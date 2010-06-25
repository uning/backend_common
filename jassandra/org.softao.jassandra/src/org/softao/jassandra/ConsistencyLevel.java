/*
 * Copyright (C) 2010 Softao.Org
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
package org.softao.jassandra;

/**
 * The value in this class is derived from <code>ConsistencyLevel</code> defined
 * in Thrift API file. It's putting here to remove the dependencies for the
 * client of Jassandra.
 * <p>
 * In most of the cases, the {@link #ONE} will be used as default value.
 */
public enum ConsistencyLevel {
	/**
	 * <ul>
	 * <li>For write: A write happens asynchronously in background.</li>
	 * <li>For read: Not supported, because it doesn't make sense.</li>
	 * </ul>
	 */
	ZERO(0),

	/**
	 * <ul>
	 * <li>For write: Ensure that the write has been written to at least 1
	 * node's commit log and memory table before responding to the client.</li>
	 * <li>For read: Will return the record returned by the first node to
	 * respond. A consistency check is always done in a background thread to fix
	 * any consistency issues when ConsistencyLevel.ONE is used. This means
	 * subsequent calls will have correct data even if the initial read gets an
	 * older value. (This is called 'read repair'.)</li>
	 * </ul>
	 */
	ONE(1),

	/**
	 * <ul>
	 * <li>For write: Ensure that the write has been written to
	 * <ReplicationFactor> / 2 + 1 nodes before responding to the client.</li>
	 * <li>For read: Will query all storage nodes and return the record with the
	 * most recent timestamp once it has at least a majority of replicas
	 * reported. Again, the remaining replicas will be checked in the
	 * background.</li>
	 * </ul>
	 */
	QUORUM(2),

	/**
	 * TODO: to be added since it's not documented in Thrift file
	 */
	DCQUORUM(3),

	/**
	 * TODO: to be added since it's not documented in Thrift file
	 */
	DCQUORUMSYNC(4),

	/**
	 * <ul>
	 * <li>For write: Ensure that the write is written to
	 * <code>&lt;ReplicationFactor&gt;</code> nodes before responding to the
	 * client.</li>
	 * <li>For read: Not yet supported, but we plan to eventually.</li>
	 * </ul>
	 */
	ALL(5),

	/**
	 * <ul>
	 * <li>Ensure that the write has been written once somewhere, including
	 * possibly being hinted in a non-target node.</li>
	 * <li>For read: Not supported. You probably want ONE instead.</li>
	 * </ul>
	 */
	ANY(6);

	private final int mValue;

	private ConsistencyLevel(int value) {
		mValue = value;
	}

	/**
	 * @return the value of this level.
	 */
	public int getValue() {
		return mValue;
	}
}
